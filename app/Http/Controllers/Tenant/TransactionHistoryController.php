<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FacilityTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionHistoryController extends Controller
{
    public function index(): View
    {
        $company = auth()->user()->company;

        $banks = $company->banks()->where('is_active', true)->get();

        $transactions = FacilityTransaction::with('bank', 'user')
            ->where('company_id', $company->id)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $totalDrawdowns  = $transactions->where('transaction_type', 'Drawdown')->sum('amount');
        $totalRepayments = $transactions->where('transaction_type', 'Repayment')->sum('amount');

        return view("livewire.tenant.{$company->slug}.transaction_history", compact(
            'company', 'banks', 'transactions', 'totalDrawdowns', 'totalRepayments'
        ));
    }

    public function store(Request $request, string $company_slug): RedirectResponse
    {
        $company = auth()->user()->company;

        $validated = $request->validate([
            'bank_id'           => 'required|exists:banks,id',
            'facility_category' => 'required|string|max:50',
            'transaction_type'  => 'required|string|max:50',
            'reference_number'  => 'nullable|string|max:100',
            'amount'            => 'required|numeric|min:0',
            'transaction_date'  => 'required|date',
            'currency'          => 'required|string|max:10',
            'remarks'           => 'nullable|string|max:500',
        ]);

        FacilityTransaction::create([
            'company_id'        => $company->id,
            'bank_id'           => $validated['bank_id'],
            'facility_category' => $validated['facility_category'],
            'transaction_type'  => $validated['transaction_type'],
            'reference_number'  => $validated['reference_number'] ?? null,
            'amount'            => $validated['amount'],
            'transaction_date'  => $validated['transaction_date'],
            'currency'          => $validated['currency'],
            'remarks'           => $validated['remarks'] ?? null,
            'user_id'           => auth()->id(),
        ]);

        AuditLog::log($company->id, 'CREATE', 'Transactions', "Recorded {$validated['transaction_type']} of LKR {$validated['amount']} for {$validated['facility_category']}");

        return redirect()
            ->route('tenant.transaction-history', ['company_slug' => $company_slug])
            ->with('success', 'Facility transaction recorded successfully.');
    }
}
