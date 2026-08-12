<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashForecast extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'forecast_from',
        'week_number',
        'opening_cash',
        'operating_inflows',
        'operating_outflows',
        'capex',
        'debt_service',
        'other',
        'currency',
        'notes',
    ];

    protected $casts = [
        'forecast_from'      => 'date',
        'opening_cash'       => 'float',
        'operating_inflows'  => 'float',
        'operating_outflows' => 'float',
        'capex'              => 'float',
        'debt_service'       => 'float',
        'other'              => 'float',
    ];

    /** Net Operating Cash = Inflows - Outflows */
    public function getNetOperatingAttribute(): float
    {
        return $this->operating_inflows - $this->operating_outflows;
    }

    /** Net Cash Flow = Net Operating - Capex - Debt Service + Other */
    public function getNetCashFlowAttribute(): float
    {
        return $this->net_operating - $this->capex - $this->debt_service + $this->other;
    }

    /** Closing Cash = Opening + Net Cash Flow */
    public function getClosingCashAttribute(): float
    {
        return $this->opening_cash + $this->net_cash_flow;
    }

    /** Label for this week, e.g. "W1 (30 Jun)" */
    public function getWeekLabelAttribute(): string
    {
        $weekStart = $this->forecast_from->copy()->addWeeks($this->week_number - 1);
        return "W{$this->week_number} (" . $weekStart->format('d M') . ')';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
