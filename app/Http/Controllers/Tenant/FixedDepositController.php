<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FixedDeposit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FixedDepositController extends Controller
{
    public function index(): View
    {
        $company = auth()->user()->company;
        $banks   = $company->banks()->where('is_active', true)->get();

        $deposits = FixedDeposit::active()
            ->with(['bank', 'user'])
            ->where('company_id', $company->id)
            ->latest('entry_date')
            ->latest('id')
            ->get();

        $totalAmount = $deposits->sum('amount');
        $totalMonthlyProfit = $deposits->sum(fn($d) => $d->monthly_profit);

        $maturingSoon = $deposits->filter(function ($d) {
            $days = $d->maturity_date ? now()->diffInDays($d->maturity_date, false) : null;
            return $days !== null && $days >= 0 && $days <= 30;
        })->count();

        $urgentFinalWeek = $deposits->filter(function ($d) {
            $days = $d->maturity_date ? now()->diffInDays($d->maturity_date, false) : null;
            return $days !== null && $days >= 0 && $days <= 7;
        })->count();

        $alreadyMatured = $deposits->filter(function ($d) {
            $days = $d->maturity_date ? now()->diffInDays($d->maturity_date, false) : null;
            return $days !== null && $days < 0;
        })->count();

        $existingRenewalInstructions = FixedDeposit::select('renewal_instructions')
            ->distinct()
            ->whereNotNull('renewal_instructions')
            ->pluck('renewal_instructions')
            ->merge(['Renew Principal + Interest', 'Renew Principal only', 'Liquidate on maturity', 'Pledged to OD'])
            ->unique()
            ->values();

        return view("livewire.tenant.{$company->slug}.fixed_deposits", compact(
            'company', 'banks', 'deposits', 'totalAmount', 'totalMonthlyProfit', 'maturingSoon', 'urgentFinalWeek',
            'alreadyMatured', 'existingRenewalInstructions'
        ));
    }

    public function store(Request $request, string $company_slug): RedirectResponse
    {
        $company = auth()->user()->company;

        $validated = $request->validate([
            'bank_id'             => 'required|exists:banks,id',
            'amount'              => 'required|numeric|min:0',
            'currency'            => 'required|in:LKR,USD',
            'commencement_date'   => 'nullable|date',
            'maturity_date'       => 'nullable|date',
            'interest_rate'       => 'required|numeric|min:0|max:100',
            'renewal_instructions'=> 'nullable|string|max:255',
            'pledged_details'     => 'nullable|string|max:500',
            'entry_date'          => 'required|date',
        ]);

        $tenor = null;
        if (!empty($validated['commencement_date']) && !empty($validated['maturity_date'])) {
            $cDate = \Carbon\Carbon::parse($validated['commencement_date']);
            $mDate = \Carbon\Carbon::parse($validated['maturity_date']);
            $days  = $cDate->diffInDays($mDate);
            $tenor = $days >= 365 ? round($days / 365, 1) . ' Years' : ($days >= 30 ? round($days / 30) . ' Months' : $days . ' Days');
        }

        $deposit = FixedDeposit::create([
            ...$validated,
            'company_id' => $company->id,
            'user_id'    => auth()->id(),
            'tenor'      => $tenor,
            'is_active'  => true,
            'version'    => 1,
        ]);

        AuditLog::log($company->id, 'CREATE', 'Fixed Deposit', "Added FD for LKR " . number_format($deposit->amount) . " at {$deposit->bank->name}");

        return redirect()
            ->route('tenant.fixed-deposits', ['company_slug' => $company_slug])
            ->with('success', 'Fixed deposit entry saved successfully.');
    }

    public function updateRate(Request $request, string $company_slug, FixedDeposit $fixedDeposit): RedirectResponse
    {
        $company = auth()->user()->company;
        if ($fixedDeposit->company_id !== $company->id) abort(403);

        $actionType = $request->input('action_type', 'withdrawal');

        $validated = $request->validate([
            'action_type'          => 'required|in:withdrawal,renew_revise',
            'bank_id'              => 'required_if:action_type,renew_revise|nullable|exists:banks,id',
            'interest_rate'        => 'required_if:action_type,renew_revise|nullable|numeric|min:0|max:100',
            'amount'               => 'required_if:action_type,renew_revise|nullable|numeric|min:0',
            'maturity_date'        => 'nullable|date',
            'withdrawal_type'      => 'required_if:action_type,withdrawal|nullable|in:all,partial',
            'withdrawn_amount'     => 'required_if:withdrawal_type,partial|nullable|numeric|min:0.01',
            'renewal_instructions' => 'nullable|string|max:255',
            'revision_notes'       => 'required|string|max:1000',
        ]);

        $oldRate = $fixedDeposit->interest_rate;
        $oldAmount = (float) $fixedDeposit->amount;
        $rootParentId = $fixedDeposit->parent_id ?? $fixedDeposit->id;

        // Archive current version
        $fixedDeposit->update(['is_active' => false]);

        if ($actionType === 'withdrawal') {
            $withdrawalType = $validated['withdrawal_type'];
            if ($withdrawalType === 'all') {
                $withdrawnAmount = $oldAmount;
                $newAmount = 0.00;
                $newIsActive = false; // Fully withdrawn FD closed
            } else {
                $withdrawnAmount = (float) $validated['withdrawn_amount'];
                $newAmount = max(0.00, $oldAmount - $withdrawnAmount);
                $newIsActive = true;
            }

            FixedDeposit::create([
                'company_id'           => $company->id,
                'bank_id'              => $fixedDeposit->bank_id,
                'user_id'              => auth()->id(),
                'parent_id'            => $rootParentId,
                'is_active'            => $newIsActive,
                'version'              => $fixedDeposit->version + 1,
                'currency'             => $fixedDeposit->currency,
                'commencement_date'    => $fixedDeposit->commencement_date,
                'maturity_date'        => $fixedDeposit->maturity_date,
                'tenor'                => $fixedDeposit->tenor,
                'interest_rate'        => $fixedDeposit->interest_rate,
                'pledged_details'      => $fixedDeposit->pledged_details,
                'renewal_instructions' => $fixedDeposit->renewal_instructions,
                'entry_date'           => now(),
                'amount'               => $newAmount,
                'action_type'          => 'withdrawal',
                'withdrawal_type'      => $withdrawalType,
                'withdrawn_amount'     => $withdrawnAmount,
                'revision_notes'       => $validated['revision_notes'],
                'revision_date'        => now(),
            ]);

            AuditLog::log($company->id, 'UPDATE', 'Fixed Deposit Withdrawal', "Withdrew LKR " . number_format($withdrawnAmount, 2) . " ({$withdrawalType}) from FD. Notes: {$validated['revision_notes']}");

            return redirect()
                ->route('tenant.fixed-deposits', ['company_slug' => $company_slug])
                ->with('success', "FD withdrawal of LKR " . number_format($withdrawnAmount, 2) . " recorded successfully.");
        }

        // Action Type: Renew / Revise FD
        $bankId = $validated['bank_id'] ?? $fixedDeposit->bank_id;
        $newAmount = (float) $validated['amount'];
        $newRate = (float) $validated['interest_rate'];
        $newMaturity = $validated['maturity_date'] ?? $fixedDeposit->maturity_date;

        FixedDeposit::create([
            'company_id'           => $company->id,
            'bank_id'              => $bankId,
            'user_id'              => auth()->id(),
            'parent_id'            => $rootParentId,
            'is_active'            => true,
            'version'              => $fixedDeposit->version + 1,
            'currency'             => $fixedDeposit->currency,
            'commencement_date'    => $fixedDeposit->commencement_date,
            'tenor'                => $fixedDeposit->tenor,
            'pledged_details'      => $fixedDeposit->pledged_details,
            'entry_date'           => now(),
            'amount'               => $newAmount,
            'maturity_date'        => $newMaturity,
            'interest_rate'        => $newRate,
            'action_type'          => 'renew_revise',
            'renewal_instructions' => $validated['renewal_instructions'] ?? $fixedDeposit->renewal_instructions,
            'revision_notes'       => $validated['revision_notes'],
            'revision_date'        => now(),
        ]);

        AuditLog::log($company->id, 'UPDATE', 'Fixed Deposit Renewal', "Renewed/Revised FD: Rate {$newRate}%, Amount LKR " . number_format($newAmount, 2));

        return redirect()
            ->route('tenant.fixed-deposits', ['company_slug' => $company_slug])
            ->with('success', "FD renewed/revised successfully. History archived.");
    }

    public function destroy(string $company_slug, FixedDeposit $fixedDeposit): RedirectResponse
    {
        $company = auth()->user()->company;
        if ($fixedDeposit->company_id !== $company->id) abort(403);

        $fixedDeposit->delete();

        AuditLog::log($company->id, 'DELETE', 'Fixed Deposit', "Deleted FD entry of LKR " . number_format($fixedDeposit->amount));

        return redirect()
            ->route('tenant.fixed-deposits', ['company_slug' => $company_slug])
            ->with('success', 'Entry deleted successfully.');
    }
}
