<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CashPositionEntry;
use App\Models\CompanyBankAccount;
use App\Models\FixedDeposit;
use App\Models\LongTermLoan;
use App\Models\WorkingCapitalLoan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    /**
     * Display the comprehensive executive summary dashboard for the Head of Finance.
     * Integrates LTL, Working Capital, Fixed Deposits, and Cash Position analytics with interactive charts.
     */
    public function summaryDashboard(): View
    {
        $company = auth()->user()->company;

        // 1. Long Term Loans
        $ltlLoans       = LongTermLoan::active()->where('company_id', $company->id)->with('bank')->get();
        $ltlOutstanding = (float) $ltlLoans->sum('outstanding_amount');
        $ltlFacilities  = (float) $ltlLoans->sum('facility_amount');
        $ltlCount       = $ltlLoans->count();
        $avgLtlRate     = $ltlLoans->count() > 0 ? (float) $ltlLoans->avg('interest_rate') : 0;

        // 2. Working Capital Loans
        $wcLoans        = WorkingCapitalLoan::active()->where('company_id', $company->id)->with('bank')->get();
        $wcOutstanding  = (float) $wcLoans->sum('outstanding_amount');
        $wcFacilities   = (float) $wcLoans->sum('facility_amount');
        $wcCount        = $wcLoans->count();
        $avgWcRate      = $wcLoans->count() > 0 ? (float) $wcLoans->avg('interest_rate') : 0;

        // Overdue & Due Soon Working Capital
        $overdueWcCount  = $wcLoans->filter(fn($l) => $l->settlement_days_overdue > 0)->count();
        $overdueWcAmount = (float) $wcLoans->filter(fn($l) => $l->settlement_days_overdue > 0)->sum('outstanding_amount');

        // 3. Fixed Deposits
        $fdList           = FixedDeposit::active()->where('company_id', $company->id)->with('bank')->get();
        $fixedDepositsTotal = (float) $fdList->sum('amount');
        $fdCount          = $fdList->count();
        $avgFdRate        = $fdList->count() > 0 ? (float) $fdList->avg('interest_rate') : 0;
        $fdMonthlyProfit  = (float) $fdList->sum(fn($fd) => ($fd->amount * ($fd->interest_rate / 100)) / 12);
        $pledgedFdAmount  = (float) $fdList->filter(fn($fd) => !empty($fd->pledged_details))->sum('amount');

        // FDs Maturing Soon (<= 30 days) and Urgent (<= 7 days)
        $fdMaturingSoonCount = $fdList->filter(function ($d) {
            $days = $d->maturity_date ? now()->diffInDays($d->maturity_date, false) : null;
            return $days !== null && $days >= 0 && $days <= 30;
        })->count();

        $fdUrgentCount = $fdList->filter(function ($d) {
            $days = $d->maturity_date ? now()->diffInDays($d->maturity_date, false) : null;
            return $days !== null && $days >= 0 && $days <= 7;
        })->count();

        // 4. Daily Cash Position Integration
        $bankAccounts = CompanyBankAccount::where('company_id', $company->id)->get();
        $latestCashEntries = CashPositionEntry::where('company_id', $company->id)
            ->latest('entry_date')
            ->latest('id')
            ->get()
            ->keyBy('company_bank_account_id');

        $totalClosingCash   = (float) $latestCashEntries->sum('closing_balance');
        $totalRestrictedCash = (float) $latestCashEntries->sum('restricted_cash');
        $availableCash      = max(0, $totalClosingCash - $totalRestrictedCash);

        // Combined Financial Totals
        $totalDebtOutstanding  = $ltlOutstanding + $wcOutstanding;
        $totalCreditFacilities = $ltlFacilities + $wcFacilities;
        $totalLiquidAssets     = $availableCash + $fixedDepositsTotal;
        $netDebtPosition       = max(0, $totalDebtOutstanding - $totalLiquidAssets);

        // Weighted Average Loan Interest Rate
        $allLoans = $ltlLoans->concat($wcLoans);
        $avgLoanRate = $allLoans->count() > 0 ? (float) $allLoans->avg('interest_rate') : 0;

        // Recent Audit Logs
        $recentAuditLogs = AuditLog::where('company_id', $company->id)
            ->with('user')
            ->latest()
            ->take(6)
            ->get();

        return view("livewire.tenant.{$company->slug}.summary_dashboard", compact(
            'company',
            'ltlLoans',
            'ltlOutstanding',
            'ltlFacilities',
            'ltlCount',
            'avgLtlRate',
            'wcLoans',
            'wcOutstanding',
            'wcFacilities',
            'wcCount',
            'avgWcRate',
            'overdueWcCount',
            'overdueWcAmount',
            'fdList',
            'fixedDepositsTotal',
            'fdCount',
            'avgFdRate',
            'fdMonthlyProfit',
            'pledgedFdAmount',
            'fdMaturingSoonCount',
            'fdUrgentCount',
            'totalDebtOutstanding',
            'totalCreditFacilities',
            'totalLiquidAssets',
            'netDebtPosition',
            'avgLoanRate',
            'availableCash',
            'totalClosingCash',
            'totalRestrictedCash',
            'bankAccounts',
            'recentAuditLogs'
        ));
    }
}
