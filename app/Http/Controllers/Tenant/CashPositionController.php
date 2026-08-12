<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Bank;
use App\Models\CashMovementEntry;
use App\Models\CashPositionEntry;
use App\Models\CompanyBankAccount;
use App\Models\FixedDeposit;
use App\Models\WorkingCapitalLoan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CashPositionController
 * ─────────────────────────────────────────────────────────────────────────────
 * Handles the Cash Position module (4 tabbed sections & bank account management).
 *
 * Routes (in routes/tenant.php):
 *   GET  /{company_slug}/cash-position              → index()
 *   POST /{company_slug}/cash-position/entry        → storeEntry()
 *   POST /{company_slug}/cash-position/movement     → storeMovement()
 *   POST /{company_slug}/cash-position/bank-account → storeBankAccount()
 */
class CashPositionController extends Controller
{
    public function index(): View
    {
        $company = auth()->user()->company;

        // Fetch all active system banks for the Add Account dropdown/modal
        $allBanks = Bank::where('is_active', true)->orderBy('name')->get();

        // Fetch all bank accounts registered for this company
        $bankAccounts = CompanyBankAccount::with('bank')
            ->where('company_id', $company->id)
            ->get();

        // Latest entries per bank account
        $latestEntries = CashPositionEntry::where('company_id', $company->id)
            ->latest('entry_date')
            ->latest('id')
            ->get()
            ->keyBy('company_bank_account_id');

        // Historical position entries
        $positionHistory = CashPositionEntry::with('bank', 'companyBankAccount')
            ->where('company_id', $company->id)
            ->latest('entry_date')
            ->latest('id')
            ->get();

        // 1. Cash Summary calculations
        $totalOpening    = $latestEntries->sum('opening_balance');
        $totalCashIn     = $latestEntries->sum('cash_in');
        $totalCashOut    = $latestEntries->sum('cash_out');
        $availableCash   = $latestEntries->sum('closing_balance');
        $restrictedCash  = $latestEntries->sum('restricted_cash');

        $fixedDepositsTotal = FixedDeposit::where('company_id', $company->id)->sum('amount');
        $totalCash          = $availableCash + $fixedDepositsTotal;
        $activeAccountsCount = $bankAccounts->count();

        // 2. Cash Movement Entry (latest or new)
        $latestMovement = CashMovementEntry::where('company_id', $company->id)
            ->latest('entry_date')
            ->latest('id')
            ->first();

        // 3. Liquidity Summary calculations
        $wcFacilities   = WorkingCapitalLoan::where('company_id', $company->id)->get();
        $availableOD    = $wcFacilities->where('facility_type', 'Overdraft')->sum(fn($l) => max(0, $l->facility_amount - $l->outstanding_amount));
        $availableWC    = $wcFacilities->sum(fn($l) => max(0, $l->facility_amount - $l->outstanding_amount));
        $totalLiquidity = $availableCash + $availableWC;

        return view("livewire.tenant.{$company->slug}.cash_position", compact(
            'company',
            'allBanks',
            'bankAccounts',
            'latestEntries',
            'positionHistory',
            'totalCash',
            'availableCash',
            'restrictedCash',
            'activeAccountsCount',
            'latestMovement',
            'availableOD',
            'availableWC',
            'totalLiquidity',
            'totalOpening',
            'totalCashIn',
            'totalCashOut'
        ));
    }

    /**
     * Store a new Bank Account for this Sub-Company, with option to create new Bank inline.
     */
    public function storeBankAccount(Request $request, string $company_slug): RedirectResponse
    {
        $company = auth()->user()->company;

        $isAddingNewBank = $request->boolean('is_adding_new_bank') || !empty($request->input('new_bank_name'));

        if ($isAddingNewBank) {
            $validatedBank = $request->validate([
                'new_bank_name' => 'required|string|max:255',
                'new_bank_code' => 'required|string|max:20',
            ]);

            $bank = Bank::firstOrCreate(
                ['bank_code' => strtoupper(trim($validatedBank['new_bank_code']))],
                ['name' => trim($validatedBank['new_bank_name']), 'is_active' => true]
            );

            $bankId = $bank->id;
        } else {
            $validatedBank = $request->validate([
                'bank_id' => 'required|exists:banks,id',
            ]);
            $bankId = $validatedBank['bank_id'];
        }

        $validated = $request->validate([
            'account_number'  => 'required|string|max:100',
            'account_type'    => 'required|string|max:100',
            'currency'        => 'required|in:LKR,USD,EUR,GBP',
            'opening_balance' => 'nullable|numeric',
            'notes'           => 'nullable|string|max:500',
        ]);

        // Associate bank with company in pivot
        $company->banks()->syncWithoutDetaching([$bankId]);

        // Create company bank account
        $account = CompanyBankAccount::create([
            'company_id'     => $company->id,
            'bank_id'        => $bankId,
            'account_number' => trim($validated['account_number']),
            'account_type'   => trim($validated['account_type']),
            'currency'       => $validated['currency'],
            'notes'          => $validated['notes'] ?? null,
        ]);

        // Create initial CashPositionEntry if opening balance is provided
        if (isset($validated['opening_balance'])) {
            $opening = (float) $validated['opening_balance'];
            CashPositionEntry::updateOrCreate(
                [
                    'company_id'              => $company->id,
                    'company_bank_account_id' => $account->id,
                    'entry_date'              => now()->toDateString(),
                ],
                [
                    'bank_id'         => $bankId,
                    'opening_balance' => $opening,
                    'cash_in'         => 0,
                    'cash_out'        => 0,
                    'restricted_cash' => 0,
                    'closing_balance' => $opening,
                    'currency'        => $account->currency,
                    'user_id'         => auth()->id(),
                ]
            );
        }

        AuditLog::log($company->id, 'CREATE', 'Bank Account', "Created bank account {$account->account_number} ({$account->bank->name})");

        return redirect()
            ->route('tenant.cash-position', ['company_slug' => $company_slug])
            ->with('success', "Bank Account '{$account->account_number}' added successfully for {$company->name}.");
    }

    /**
     * Delete a bank account and its associated cash position entries.
     */
    public function destroyBankAccount(string $company_slug, CompanyBankAccount $bankAccount): RedirectResponse
    {
        $company = auth()->user()->company;
        if ($bankAccount->company_id !== $company->id) abort(403);

        $accNo = $bankAccount->account_number;

        CashPositionEntry::where('company_bank_account_id', $bankAccount->id)->delete();
        $bankAccount->delete();

        AuditLog::log($company->id, 'DELETE', 'Bank Account', "Deleted bank account {$accNo}");

        return redirect()
            ->route('tenant.cash-position', ['company_slug' => $company_slug])
            ->with('success', "Bank account '{$accNo}' deleted successfully.");
    }

    /**
     * Store or update a bank account cash position entry.
     */
    public function storeEntry(Request $request, string $company_slug): RedirectResponse
    {
        $company = auth()->user()->company;

        $validated = $request->validate([
            'company_bank_account_id' => 'required|exists:company_bank_accounts,id',
            'opening_balance'         => 'required|numeric',
            'cash_in'                 => 'required|numeric|min:0',
            'cash_out'                => 'required|numeric|min:0',
            'restricted_cash'         => 'nullable|numeric|min:0',
            'entry_date'              => 'required|date',
            'remarks'                 => 'nullable|string|max:500',
        ]);

        $account = CompanyBankAccount::where('company_id', $company->id)->findOrFail($validated['company_bank_account_id']);

        $opening  = (float) $validated['opening_balance'];
        $cashIn   = (float) $validated['cash_in'];
        $cashOut  = (float) $validated['cash_out'];
        $closing  = $opening + $cashIn - $cashOut;
        $restr    = (float) ($validated['restricted_cash'] ?? 0);

        CashPositionEntry::updateOrCreate(
            [
                'company_id'              => $company->id,
                'company_bank_account_id' => $account->id,
                'entry_date'              => $validated['entry_date'],
            ],
            [
                'bank_id'         => $account->bank_id,
                'opening_balance' => $opening,
                'cash_in'         => $cashIn,
                'cash_out'        => $cashOut,
                'restricted_cash' => $restr,
                'closing_balance' => $closing,
                'currency'        => $account->currency,
                'remarks'         => $validated['remarks'] ?? null,
                'user_id'         => auth()->id(),
            ]
        );

        return redirect()
            ->route('tenant.cash-position', ['company_slug' => $company_slug])
            ->with('success', 'Cash position entry updated successfully.');
    }

    /**
     * Store or update cash movement breakdown entry.
     */
    public function storeMovement(Request $request, string $company_slug): RedirectResponse
    {
        $company = auth()->user()->company;

        $validated = $request->validate([
            'customer_collections' => 'required|numeric|min:0',
            'loan_drawdowns'       => 'required|numeric|min:0',
            'supplier_payments'    => 'required|numeric|min:0',
            'salaries'             => 'required|numeric|min:0',
            'taxes'                => 'required|numeric|min:0',
            'loan_repayments'      => 'required|numeric|min:0',
            'other_payments'       => 'required|numeric|min:0',
            'entry_date'           => 'required|date',
            'remarks'              => 'nullable|string|max:500',
        ]);

        CashMovementEntry::updateOrCreate(
            [
                'company_id' => $company->id,
                'entry_date' => $validated['entry_date'],
            ],
            [
                ...$validated,
                'user_id' => auth()->id(),
            ]
        );

        return redirect()
            ->route('tenant.cash-position', ['company_slug' => $company_slug])
            ->with('success', 'Cash movement breakdown updated successfully.');
    }
}
