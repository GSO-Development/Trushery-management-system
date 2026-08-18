<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LongTermLoan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LongTermLoanController extends Controller
{
    /**
     * Show Long Term Loans page with active entries and revision history.
     */
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

        // Only fetch active top-level/current records, with their histories eager-loaded
        $loans = LongTermLoan::active()
            ->with(['bank', 'user'])
            ->where('company_id', $company->id)
            ->latest('entry_date')
            ->latest('id')
            ->get();

        $totalFacility    = $loans->sum('facility_amount');
        $totalOutstanding = $loans->sum('outstanding_amount');

        $loansGrouped = $loans->groupBy(fn($l) => $l->entry_date ? $l->entry_date->format('F Y') : 'Unspecified Period');

        $existingLoanTypes = LongTermLoan::select('loan_type')
            ->distinct()
            ->pluck('loan_type')
            ->merge(['Capex funding', 'Vehicle', 'Term Loan', 'Moratorium Loan', 'Intercompany'])
            ->unique()
            ->values();

        $existingTenors = LongTermLoan::select('tenor')
            ->distinct()
            ->pluck('tenor')
            ->merge(['1m', '3m', '6m', '12m / 1y', '2y', '3y', '4y', '5y', '10y'])
            ->unique()
            ->values();

        return view("livewire.tenant.{$company->slug}.long_term_loans", compact(
            'company', 'banks', 'loans', 'loansGrouped', 'totalFacility', 'totalOutstanding',
            'existingLoanTypes', 'existingTenors', 'filterMonth', 'filterYear', 'filterMonthName', 'filterDateFormatted'
        ));
    }

    /**
     * Store a new Long Term Loan entry.
     */
    public function store(Request $request, string $company_slug): RedirectResponse
    {
        $company = auth()->user()->company;

        $validated = $request->validate([
            'bank_id'               => 'required|exists:banks,id',
            'loan_type'             => 'required|string|max:100',
            'tenor'                 => 'required|string|max:100',
            'facility_amount'       => 'required|numeric|min:0',
            'granted_date'          => 'nullable|date',
            'interest_rate'         => 'required|numeric|min:0|max:100',
            'remaining_tenor_months'=> 'nullable|integer|min:0',
            'outstanding_amount'    => 'required|numeric|min:0',
            'currency'              => 'required|in:LKR,USD',
            'notes'                 => 'nullable|string|max:1000',
            'entry_date'            => 'required|date',
        ]);

        $loan = LongTermLoan::create([
            ...$validated,
            'company_id' => $company->id,
            'user_id'    => auth()->id(),
            'is_active'  => true,
            'version'    => 1,
        ]);

        AuditLog::log($company->id, 'CREATE', 'Long Term Loan', "Added {$loan->loan_type} for LKR " . number_format($loan->facility_amount));

        return redirect()
            ->route('tenant.long-term-loans', ['company_slug' => $company_slug])
            ->with('success', 'Long term loan entry saved successfully.');
    }

    /**
     * Inline Rate & Terms Revision / Settlement — Archives previous version to history.
     */
    public function updateRate(Request $request, string $company_slug, LongTermLoan $loan): RedirectResponse
    {
        $company = auth()->user()->company;
        if ($loan->company_id !== $company->id) abort(403);

        $actionType = $request->input('action_type', 'rate_change');

        $validated = $request->validate([
            'action_type'           => 'required|in:rate_change,settle_loan',
            'interest_rate'         => 'required_if:action_type,rate_change|nullable|numeric|min:0|max:100',
            'outstanding_amount'    => 'required_if:action_type,rate_change|nullable|numeric|min:0',
            'settlement_type'       => 'required_if:action_type,settle_loan|nullable|in:all,partial',
            'settled_amount'        => 'required_if:settlement_type,partial|nullable|numeric|min:0.01',
            'settle_using_new_loan' => 'nullable',
            'settled_via_loan_id'   => 'nullable|exists:long_term_loans,id',
            'revision_notes'        => 'required|string|max:1000',
        ]);

        $oldRate = $loan->interest_rate;
        $oldOutstanding = (float) $loan->outstanding_amount;
        $rootParentId = $loan->parent_id ?? $loan->id;

        // Deactivate current active record
        $loan->update(['is_active' => false]);

        if ($actionType === 'settle_loan') {
            $settlementType = $validated['settlement_type'];
            if ($settlementType === 'all') {
                $settledAmount = $oldOutstanding;
                $newOutstanding = 0.00;
                $newIsActive = false; // Fully settled loan is completed
            } else {
                $settledAmount = (float) $validated['settled_amount'];
                $newOutstanding = max(0.00, $oldOutstanding - $settledAmount);
                $newIsActive = true;
            }

            $settledViaLoanId = $request->boolean('settle_using_new_loan') ? ($validated['settled_via_loan_id'] ?? null) : null;

            $newLoan = LongTermLoan::create([
                'company_id'             => $company->id,
                'bank_id'                => $loan->bank_id,
                'user_id'                => auth()->id(),
                'parent_id'              => $rootParentId,
                'is_active'              => $newIsActive,
                'version'                => $loan->version + 1,
                'loan_type'              => $loan->loan_type,
                'tenor'                  => $loan->tenor,
                'facility_amount'        => $loan->facility_amount,
                'granted_date'           => $loan->granted_date,
                'remaining_tenor_months' => $loan->remaining_tenor_months,
                'currency'               => $loan->currency,
                'notes'                  => $loan->notes,
                'entry_date'             => now(),
                'interest_rate'          => $loan->interest_rate,
                'outstanding_amount'     => $newOutstanding,
                'action_type'            => 'settle_loan',
                'settlement_type'        => $settlementType,
                'settled_amount'         => $settledAmount,
                'settled_via_loan_id'    => $settledViaLoanId,
                'revision_notes'         => $validated['revision_notes'],
                'revision_date'          => now(),
            ]);

            AuditLog::log($company->id, 'UPDATE', 'Long Term Loan Settlement', "Settled LKR " . number_format($settledAmount, 2) . " ({$settlementType}) for {$loan->loan_type}. Notes: {$validated['revision_notes']}");

            return redirect()
                ->route('tenant.long-term-loans', ['company_slug' => $company_slug])
                ->with('success', "Loan settlement of LKR " . number_format($settledAmount, 2) . " recorded successfully.");
        }

        // Action Type: Rate Change
        $newRate = (float) $validated['interest_rate'];
        $newOutstanding = (float) $validated['outstanding_amount'];

        $newLoan = LongTermLoan::create([
            'company_id'             => $company->id,
            'bank_id'                => $loan->bank_id,
            'user_id'                => auth()->id(),
            'parent_id'              => $rootParentId,
            'is_active'              => true,
            'version'                => $loan->version + 1,
            'loan_type'              => $loan->loan_type,
            'tenor'                  => $loan->tenor,
            'facility_amount'        => $loan->facility_amount,
            'granted_date'           => $loan->granted_date,
            'remaining_tenor_months' => $loan->remaining_tenor_months,
            'currency'               => $loan->currency,
            'notes'                  => $loan->notes,
            'entry_date'             => now(),
            'interest_rate'          => $newRate,
            'outstanding_amount'     => $newOutstanding,
            'action_type'            => 'rate_change',
            'revision_notes'         => $validated['revision_notes'],
            'revision_date'          => now(),
        ]);

        AuditLog::log($company->id, 'UPDATE', 'Long Term Loan Rate', "Revised interest rate from {$oldRate}% to {$newRate}% for {$loan->loan_type}");

        return redirect()
            ->route('tenant.long-term-loans', ['company_slug' => $company_slug])
            ->with('success', "Rate updated from {$oldRate}% to {$newRate}%. Revision history logged.");
    }

    /**
     * Delete loan entry.
     */
    public function destroy(string $company_slug, LongTermLoan $loan): RedirectResponse
    {
        $company = auth()->user()->company;
        if ($loan->company_id !== $company->id) abort(403);

        $loan->delete();

        AuditLog::log($company->id, 'DELETE', 'Long Term Loan', "Deleted {$loan->loan_type} entry");

        return redirect()
            ->route('tenant.long-term-loans', ['company_slug' => $company_slug])
            ->with('success', 'Entry deleted successfully.');
    }
}
