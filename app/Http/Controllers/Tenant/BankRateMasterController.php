<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BankRateMaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankRateMasterController extends Controller
{
    public function index(): View
    {
        $company = auth()->user()->company;

        $banks = $company->banks()->where('is_active', true)->get();

        $rates = BankRateMaster::with('bank', 'user')
            ->where('company_id', $company->id)
            ->orderBy('effective_date', 'desc')
            ->get();

        return view("livewire.tenant.{$company->slug}.rate_master", compact(
            'company', 'banks', 'rates'
        ));
    }

    public function store(Request $request, string $company_slug): RedirectResponse
    {
        $company = auth()->user()->company;

        $validated = $request->validate([
            'bank_id'        => 'required|exists:banks,id',
            'rate_type'      => 'required|string|max:50',
            'base_rate'      => 'required|numeric|min:0|max:100',
            'margin'         => 'required|numeric|min:0|max:100',
            'effective_date' => 'required|date',
            'remarks'        => 'nullable|string|max:500',
        ]);

        $base      = (float) $validated['base_rate'];
        $margin    = (float) $validated['margin'];
        $effective = $base + $margin;

        BankRateMaster::create([
            'company_id'     => $company->id,
            'bank_id'        => $validated['bank_id'],
            'rate_type'      => $validated['rate_type'],
            'base_rate'      => $base,
            'margin'         => $margin,
            'effective_rate' => $effective,
            'effective_date' => $validated['effective_date'],
            'remarks'        => $validated['remarks'] ?? null,
            'user_id'        => auth()->id(),
        ]);

        AuditLog::log($company->id, 'CREATE', 'Interest Rates', "Added {$validated['rate_type']} rate revision for bank #{$validated['bank_id']} (Effective: {$effective}%)");

        return redirect()
            ->route('tenant.rate-master', ['company_slug' => $company_slug])
            ->with('success', 'Bank interest rate revision recorded successfully.');
    }
}
