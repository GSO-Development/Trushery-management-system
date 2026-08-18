<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\CashPositionEntry;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use App\Models\FixedDeposit;
use App\Models\LongTermLoan;
use App\Models\WorkingCapitalLoan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupComparisonController extends Controller
{
    /**
     * Display multi-company comparative analytics & bank interest rate matrix.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Get companies accessible to user (group-scoped or all)
        if ($user->is_admin) {
            $companies = Company::where('slug', '!=', 'admin')->with('banks')->orderBy('name')->get();
        } elseif ($user->group && $user->group->isGroup() && ! empty($user->group->company_ids)) {
            $companies = Company::whereIn('id', $user->group->company_ids)->with('banks')->orderBy('name')->get();
        } else {
            $companies = $user->ceoCompanies()->with('banks')->orderBy('name')->get();
            if ($companies->isEmpty()) {
                $companies = Company::where('slug', '!=', 'admin')->with('banks')->orderBy('name')->get();
            }
        }

        $companyIds = $companies->pluck('id');

        // Active category tab for highlighting (defaults to long_term_loans)
        $selectedCategory = $request->query('category', 'long_term_loans');

        // Selected company filter (from form submission, default to all)
        $rawSelectedIds = $request->query('company_ids', null);
        $selectedCompanyIds = $rawSelectedIds ? array_map('intval', (array) $rawSelectedIds) : $companyIds->toArray();

        // Filter companies by selectedCompanyIds
        $filteredCompanies = $companies->whereIn('id', $selectedCompanyIds)->values();

        // Fetch active instruments scoped to filteredCompanies
        $filteredIds = $filteredCompanies->pluck('id');

        $longTermLoans = LongTermLoan::active()
            ->with(['bank', 'company'])
            ->whereIn('company_id', $companyIds)
            ->get();

        $workingCapitals = WorkingCapitalLoan::active()
            ->with(['bank', 'company'])
            ->whereIn('company_id', $companyIds)
            ->get();

        $fixedDeposits = FixedDeposit::active()
            ->with(['bank', 'company'])
            ->whereIn('company_id', $companyIds)
            ->get();

        // Latest Cash Positions
        $bankAccounts = CompanyBankAccount::whereIn('company_id', $companyIds)->get();
        $latestCashEntries = CashPositionEntry::whereIn('company_id', $companyIds)
            ->latest('entry_date')
            ->latest('id')
            ->get()
            ->keyBy('company_bank_account_id');

        // Pick the correct loan dataset based on selected category
        $categoryData = match ($selectedCategory) {
            'working_capital'  => $workingCapitals->whereIn('company_id', $filteredIds)->values(),
            'fixed_deposits'   => $fixedDeposits->whereIn('company_id', $filteredIds)->values(),
            default            => $longTermLoans->whereIn('company_id', $filteredIds)->values(),
        };
        $amountField = $selectedCategory === 'fixed_deposits' ? 'amount' : 'outstanding_amount';

        // Build Bank x Company matrix expected by the blade
        // $matrix[$bankId] = ['bank' => Bank, 'companies' => [$compId => ['count', 'avg_rate', 'amount']], 'total_count', 'total_amount', 'avg_rate']
        $banks = Bank::where('is_active', true)->orderBy('name')->get();
        $matrix = [];

        foreach ($banks as $bank) {
            $bankItems = $categoryData->where('bank_id', $bank->id);
            if ($bankItems->isEmpty()) continue;

            $companyCells = [];
            foreach ($filteredCompanies as $comp) {
                $items = $bankItems->where('company_id', $comp->id);
                $companyCells[$comp->id] = [
                    'count'    => $items->count(),
                    'avg_rate' => $items->count() > 0 ? (float) $items->avg('interest_rate') : 0,
                    'amount'   => (float) $items->sum($amountField),
                ];
            }

            $matrix[$bank->id] = [
                'bank'         => $bank,
                'companies'    => $companyCells,
                'total_count'  => $bankItems->count(),
                'total_amount' => (float) $bankItems->sum($amountField),
                'avg_rate'     => $bankItems->count() > 0 ? (float) $bankItems->avg('interest_rate') : 0,
            ];
        }

        // Bank Rate Summary (for lower summary table) - across ALL category types
        $bankRates = $banks->map(function (Bank $bank) use ($longTermLoans, $workingCapitals, $fixedDeposits) {
            $bankLtl = $longTermLoans->where('bank_id', $bank->id);
            $bankWc  = $workingCapitals->where('bank_id', $bank->id);
            $bankFd  = $fixedDeposits->where('bank_id', $bank->id);
            $allLoans = $bankLtl->concat($bankWc);

            return [
                'bank'            => $bank,
                'loan_count'      => $allLoans->count(),
                'total_exposure'  => (float) $allLoans->sum('outstanding_amount'),
                'min_loan_rate'   => $allLoans->min('interest_rate') ?? 0,
                'max_loan_rate'   => $allLoans->max('interest_rate') ?? 0,
                'avg_loan_rate'   => $allLoans->avg('interest_rate') ?? 0,
                'fd_count'        => $bankFd->count(),
                'total_fd_volume' => (float) $bankFd->sum('amount'),
                'min_fd_rate'     => $bankFd->min('interest_rate') ?? 0,
                'max_fd_rate'     => $bankFd->max('interest_rate') ?? 0,
                'avg_fd_rate'     => $bankFd->avg('interest_rate') ?? 0,
            ];
        })->filter(fn($b) => $b['loan_count'] > 0 || $b['fd_count'] > 0);

        return view('ceo.comparison', compact(
            'companies',
            'filteredCompanies',
            'matrix',
            'bankRates',
            'banks',
            'longTermLoans',
            'workingCapitals',
            'fixedDeposits',
            'selectedCategory',
            'selectedCompanyIds'
        ));
    }
}