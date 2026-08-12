<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Models\CashPositionEntry;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\FixedDeposit;
use App\Models\LongTermLoan;
use App\Models\WorkingCapitalLoan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CeoDashboardController extends Controller
{
    /**
     * Display the consolidated multi-company dashboard overview for CEO / Group Treasury.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->is_admin) {
            $ceoCompanies = Company::where('slug', '!=', 'admin')->with('banks')->get();
        } else {
            $ceoCompanies = $user->ceoCompanies()->with('banks')->get();
            if ($ceoCompanies->isEmpty()) {
                $ceoCompanies = Company::where('slug', '!=', 'admin')->with('banks')->get();
            }
        }

        $companyIds = $ceoCompanies->pluck('id');

        // Selected company ID for drilldown
        $selectedCompanyId = (int) $request->query('company_id', $ceoCompanies->first()?->id);

        // 1. Long Term Loans
        $allLongTermLoans = LongTermLoan::active()
            ->with(['bank', 'company'])
            ->whereIn('company_id', $companyIds)
            ->get();

        $groupLtlOutstanding = (float) $allLongTermLoans->sum('outstanding_amount');
        $groupLtlFacility    = (float) $allLongTermLoans->sum('facility_amount');
        $groupLtlCount       = $allLongTermLoans->count();

        // 2. Working Capital Loans
        $allWorkingCapital = WorkingCapitalLoan::active()
            ->with(['bank', 'company'])
            ->whereIn('company_id', $companyIds)
            ->get();

        $groupWcOutstanding = (float) $allWorkingCapital->sum('outstanding_amount');
        $groupWcFacility    = (float) $allWorkingCapital->sum('facility_amount');
        $groupWcCount       = $allWorkingCapital->count();

        // 3. Fixed Deposits
        $allFixedDeposits = FixedDeposit::active()
            ->with(['bank', 'company'])
            ->whereIn('company_id', $companyIds)
            ->get();

        $groupFdTotal        = (float) $allFixedDeposits->sum('amount');
        $groupFdCount        = $allFixedDeposits->count();
        $groupFdMonthlyYield = (float) $allFixedDeposits->sum(fn($fd) => ($fd->amount * ($fd->interest_rate / 100)) / 12);

        // 4. Daily Cash Position Integration
        $allBankAccounts = CompanyBankAccount::whereIn('company_id', $companyIds)->get();
        $latestCashEntries = CashPositionEntry::whereIn('company_id', $companyIds)
            ->latest('entry_date')
            ->latest('id')
            ->get()
            ->keyBy('company_bank_account_id');

        $groupClosingCash    = (float) $latestCashEntries->sum('closing_balance');
        $groupRestrictedCash = (float) $latestCashEntries->sum('restricted_cash');
        $groupAvailableCash  = max(0, $groupClosingCash - $groupRestrictedCash);

        // Per-company summaries for interactive graph & cards
        $companySummaries = $ceoCompanies->map(function (Company $company) use (
            $allLongTermLoans,
            $allWorkingCapital,
            $allFixedDeposits,
            $allBankAccounts,
            $latestCashEntries
        ) {
            $compLtl   = $allLongTermLoans->where('company_id', $company->id);
            $compWc    = $allWorkingCapital->where('company_id', $company->id);
            $compFd    = $allFixedDeposits->where('company_id', $company->id);
            $compAccs  = $allBankAccounts->where('company_id', $company->id);

            $compCash = 0;
            foreach ($compAccs as $acc) {
                $entry = $latestCashEntries->get($acc->id);
                if ($entry) {
                    $compCash += max(0, $entry->closing_balance - $entry->restricted_cash);
                }
            }

            $ltlOut  = (float) $compLtl->sum('outstanding_amount');
            $wcOut   = (float) $compWc->sum('outstanding_amount');
            $fdSum   = (float) $compFd->sum('amount');

            $allCompanyLoans = $compLtl->concat($compWc);
            $avgLoanRate = $allCompanyLoans->count() > 0 ? (float) $allCompanyLoans->avg('interest_rate') : 0;

            return [
                'id'            => $company->id,
                'name'          => $company->name,
                'slug'          => $company->slug,
                'ltlDebt'       => $ltlOut,
                'ltlFacility'   => (float) $compLtl->sum('facility_amount'),
                'ltlCount'      => $compLtl->count(),
                'wcDebt'        => $wcOut,
                'wcFacility'    => (float) $compWc->sum('facility_amount'),
                'wcCount'       => $compWc->count(),
                'fdAmount'      => $fdSum,
                'fdCount'       => $compFd->count(),
                'fdMonthlyYield'=> (float) $compFd->sum(fn($fd) => ($fd->amount * ($fd->interest_rate / 100)) / 12),
                'availableCash' => $compCash,
                'avgLoanRate'   => $avgLoanRate,
                'totalDebt'     => $ltlOut + $wcOut,
            ];
        });

        // Detail data for selected company
        $selectedSummary = $companySummaries->firstWhere('id', $selectedCompanyId)
            ?? $companySummaries->first();

        return view('ceo.dashboard', compact(
            'ceoCompanies',
            'companySummaries',
            'selectedSummary',
            'selectedCompanyId',
            'groupAvailableCash',
            'groupLtlOutstanding',
            'groupLtlFacility',
            'groupLtlCount',
            'groupWcOutstanding',
            'groupWcFacility',
            'groupWcCount',
            'groupFdTotal',
            'groupFdCount',
            'groupFdMonthlyYield',
            'allLongTermLoans',
            'allWorkingCapital',
            'allFixedDeposits'
        ));
    }
}
