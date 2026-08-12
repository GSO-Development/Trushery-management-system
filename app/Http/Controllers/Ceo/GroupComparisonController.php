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

        // Get companies accessible to user (All active companies for Admin/CEO)
        if ($user->is_admin) {
            $companies = Company::where('slug', '!=', 'admin')->orderBy('name')->get();
        } else {
            $companies = $user->ceoCompanies()->orderBy('name')->get();
            if ($companies->isEmpty()) {
                $companies = Company::where('slug', '!=', 'admin')->orderBy('name')->get();
            }
        }

        // Filter Inputs
        $selectedCompanyIds = $request->input('company_ids', $companies->pluck('id')->toArray());
        if (!is_array($selectedCompanyIds)) {
            $selectedCompanyIds = explode(',', $selectedCompanyIds);
        }
        $selectedCompanyIds = array_map('intval', array_filter($selectedCompanyIds));
        if (empty($selectedCompanyIds)) {
            $selectedCompanyIds = $companies->pluck('id')->toArray();
        }

        $selectedCategory = $request->input('category', 'long_term_loans');

        $filteredCompanies = $companies->whereIn('id', $selectedCompanyIds)->values();

        // Matrix Data & Items Data
        $matrix = [];
        $items  = collect();
        $banks  = collect();

        if ($selectedCategory === 'long_term_loans') {
            $items = LongTermLoan::active()
                ->with(['bank', 'company'])
                ->whereIn('company_id', $selectedCompanyIds)
                ->get();
            
            $banks = Bank::whereIn('id', $items->pluck('bank_id')->unique())->orderBy('name')->get();

            foreach ($banks as $bank) {
                $matrix[$bank->id] = [
                    'bank' => $bank,
                    'companies' => [],
                    'total_count' => 0,
                    'total_amount' => 0,
                    'avg_rate' => 0,
                ];

                $bankItems = $items->where('bank_id', $bank->id);
                $matrix[$bank->id]['total_count']  = $bankItems->count();
                $matrix[$bank->id]['total_amount'] = $bankItems->sum('outstanding_amount');
                $matrix[$bank->id]['avg_rate']     = $bankItems->count() > 0 ? $bankItems->avg('interest_rate') : 0;

                foreach ($filteredCompanies as $comp) {
                    $compBankItems = $bankItems->where('company_id', $comp->id);
                    $matrix[$bank->id]['companies'][$comp->id] = [
                        'count'       => $compBankItems->count(),
                        'avg_rate'    => $compBankItems->count() > 0 ? $compBankItems->avg('interest_rate') : 0,
                        'amount'      => $compBankItems->sum('outstanding_amount'),
                        'facility'    => $compBankItems->sum('facility_amount'),
                    ];
                }
            }
        } elseif ($selectedCategory === 'working_capital') {
            $items = WorkingCapitalLoan::active()
                ->with(['bank', 'company'])
                ->whereIn('company_id', $selectedCompanyIds)
                ->get();

            $banks = Bank::whereIn('id', $items->pluck('bank_id')->unique())->orderBy('name')->get();

            foreach ($banks as $bank) {
                $matrix[$bank->id] = [
                    'bank' => $bank,
                    'companies' => [],
                    'total_count' => 0,
                    'total_amount' => 0,
                    'avg_rate' => 0,
                ];

                $bankItems = $items->where('bank_id', $bank->id);
                $matrix[$bank->id]['total_count']  = $bankItems->count();
                $matrix[$bank->id]['total_amount'] = $bankItems->sum('outstanding_amount');
                $matrix[$bank->id]['avg_rate']     = $bankItems->count() > 0 ? $bankItems->avg('interest_rate') : 0;

                foreach ($filteredCompanies as $comp) {
                    $compBankItems = $bankItems->where('company_id', $comp->id);
                    $matrix[$bank->id]['companies'][$comp->id] = [
                        'count'       => $compBankItems->count(),
                        'avg_rate'    => $compBankItems->count() > 0 ? $compBankItems->avg('interest_rate') : 0,
                        'amount'      => $compBankItems->sum('outstanding_amount'),
                        'facility'    => $compBankItems->sum('facility_amount'),
                    ];
                }
            }
        } elseif ($selectedCategory === 'fixed_deposits') {
            $items = FixedDeposit::active()
                ->with(['bank', 'company'])
                ->whereIn('company_id', $selectedCompanyIds)
                ->get();

            $banks = Bank::whereIn('id', $items->pluck('bank_id')->unique())->orderBy('name')->get();

            foreach ($banks as $bank) {
                $matrix[$bank->id] = [
                    'bank' => $bank,
                    'companies' => [],
                    'total_count' => 0,
                    'total_amount' => 0,
                    'avg_rate' => 0,
                ];

                $bankItems = $items->where('bank_id', $bank->id);
                $matrix[$bank->id]['total_count']  = $bankItems->count();
                $matrix[$bank->id]['total_amount'] = $bankItems->sum('amount');
                $matrix[$bank->id]['avg_rate']     = $bankItems->count() > 0 ? $bankItems->avg('interest_rate') : 0;

                foreach ($filteredCompanies as $comp) {
                    $compBankItems = $bankItems->where('company_id', $comp->id);
                    $matrix[$bank->id]['companies'][$comp->id] = [
                        'count'       => $compBankItems->count(),
                        'avg_rate'    => $compBankItems->count() > 0 ? $compBankItems->avg('interest_rate') : 0,
                        'amount'      => $compBankItems->sum('amount'),
                        'facility'    => $compBankItems->sum('amount'),
                    ];
                }
            }
        } elseif ($selectedCategory === 'cash_position') {
            $accounts = CompanyBankAccount::with(['bank', 'company'])
                ->whereIn('company_id', $selectedCompanyIds)
                ->get();

            $latestEntries = CashPositionEntry::whereIn('company_id', $selectedCompanyIds)
                ->latest('entry_date')
                ->latest('id')
                ->get()
                ->keyBy('company_bank_account_id');

            $banks = Bank::whereIn('id', $accounts->pluck('bank_id')->unique())->orderBy('name')->get();

            foreach ($banks as $bank) {
                $matrix[$bank->id] = [
                    'bank' => $bank,
                    'companies' => [],
                    'total_count' => 0,
                    'total_amount' => 0,
                    'avg_rate' => 0,
                ];

                $bankAccs = $accounts->where('bank_id', $bank->id);
                $matrix[$bank->id]['total_count'] = $bankAccs->count();

                $bankSum = 0;
                foreach ($bankAccs as $acc) {
                    $entry = $latestEntries->get($acc->id);
                    if ($entry) {
                        $bankSum += max(0, $entry->closing_balance - $entry->restricted_cash);
                    }
                }
                $matrix[$bank->id]['total_amount'] = $bankSum;

                foreach ($filteredCompanies as $comp) {
                    $compAccs = $bankAccs->where('company_id', $comp->id);
                    $compSum = 0;
                    foreach ($compAccs as $acc) {
                        $entry = $latestEntries->get($acc->id);
                        if ($entry) {
                            $compSum += max(0, $entry->closing_balance - $entry->restricted_cash);
                        }
                    }

                    $matrix[$bank->id]['companies'][$comp->id] = [
                        'count'    => $compAccs->count(),
                        'avg_rate' => 0,
                        'amount'   => $compSum,
                        'facility' => $compSum,
                    ];
                }
            }
        }

        return view('ceo.comparison', compact(
            'companies',
            'selectedCompanyIds',
            'filteredCompanies',
            'selectedCategory',
            'banks',
            'matrix',
            'items'
        ));
    }
}
