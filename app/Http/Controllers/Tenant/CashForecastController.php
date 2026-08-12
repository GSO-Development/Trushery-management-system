<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CashForecast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashForecastController extends Controller
{
    public function index(Request $request): View
    {
        $company = auth()->user()->company;

        // Determine which forecast period to show — default to current Monday
        $forecastFrom = $request->filled('forecast_from')
            ? \Carbon\Carbon::parse($request->query('forecast_from'))->startOfWeek()
            : now()->startOfWeek();

        // Get existing rows for this period
        $existingRows = CashForecast::where('company_id', $company->id)
            ->where('forecast_from', $forecastFrom->toDateString())
            ->orderBy('week_number')
            ->get()
            ->keyBy('week_number');

        // Build 13-week slot array (fill with defaults if not saved yet)
        $weeks = collect(range(1, 13))->map(function ($wn) use ($existingRows, $forecastFrom) {
            $existing = $existingRows->get($wn);
            $weekStart = $forecastFrom->copy()->addWeeks($wn - 1);

            return [
                'week_number'        => $wn,
                'week_label'         => 'W' . $wn . ' (' . $weekStart->format('d M') . ')',
                'week_start'         => $weekStart->format('Y-m-d'),
                'id'                 => $existing?->id,
                'opening_cash'       => $existing?->opening_cash ?? 0,
                'operating_inflows'  => $existing?->operating_inflows ?? 0,
                'operating_outflows' => $existing?->operating_outflows ?? 0,
                'net_operating'      => $existing?->net_operating ?? 0,
                'capex'              => $existing?->capex ?? 0,
                'debt_service'       => $existing?->debt_service ?? 0,
                'other'              => $existing?->other ?? 0,
                'net_cash_flow'      => $existing?->net_cash_flow ?? 0,
                'closing_cash'       => $existing?->closing_cash ?? 0,
                'notes'              => $existing?->notes ?? '',
            ];
        });

        // All existing forecast periods for this company (for switcher)
        $forecastPeriods = CashForecast::where('company_id', $company->id)
            ->select('forecast_from')
            ->distinct()
            ->orderByDesc('forecast_from')
            ->get()
            ->pluck('forecast_from')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->unique();

        return view("livewire.tenant.{$company->slug}.cash_forecast", compact(
            'company', 'weeks', 'forecastFrom', 'forecastPeriods'
        ));
    }

    public function store(Request $request, string $company_slug): RedirectResponse
    {
        $company = auth()->user()->company;

        $validated = $request->validate([
            'forecast_from'   => 'required|date',
            'weeks'           => 'required|array|min:1|max:13',
            'weeks.*.week_number'        => 'required|integer|min:1|max:13',
            'weeks.*.opening_cash'       => 'nullable|numeric',
            'weeks.*.operating_inflows'  => 'nullable|numeric',
            'weeks.*.operating_outflows' => 'nullable|numeric',
            'weeks.*.capex'              => 'nullable|numeric',
            'weeks.*.debt_service'       => 'nullable|numeric',
            'weeks.*.other'              => 'nullable|numeric',
            'weeks.*.notes'              => 'nullable|string|max:500',
        ]);

        $forecastFrom = \Carbon\Carbon::parse($validated['forecast_from'])->startOfWeek()->toDateString();

        foreach ($validated['weeks'] as $weekData) {
            CashForecast::updateOrCreate(
                [
                    'company_id'   => $company->id,
                    'forecast_from' => $forecastFrom,
                    'week_number'  => $weekData['week_number'],
                ],
                [
                    'user_id'            => auth()->id(),
                    'opening_cash'       => (float) ($weekData['opening_cash'] ?? 0),
                    'operating_inflows'  => (float) ($weekData['operating_inflows'] ?? 0),
                    'operating_outflows' => (float) ($weekData['operating_outflows'] ?? 0),
                    'capex'              => (float) ($weekData['capex'] ?? 0),
                    'debt_service'       => (float) ($weekData['debt_service'] ?? 0),
                    'other'              => (float) ($weekData['other'] ?? 0),
                    'currency'           => 'LKR',
                    'notes'              => $weekData['notes'] ?? null,
                ]
            );
        }

        AuditLog::log($company->id, 'UPDATE', 'Cash Forecast', "Updated 13-Week Cash Forecast starting {$forecastFrom}");

        return redirect()
            ->route('tenant.cash-forecast', ['company_slug' => $company_slug, 'forecast_from' => $forecastFrom])
            ->with('success', '13-Week Cash Flow Forecast saved successfully.');
    }
}
