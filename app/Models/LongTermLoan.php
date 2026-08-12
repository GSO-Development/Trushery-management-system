<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LongTermLoan extends Model
{
    protected $fillable = [
        'company_id', 'bank_id', 'user_id',
        'parent_id', 'is_active', 'version',
        'loan_type', 'tenor', 'facility_amount',
        'granted_date', 'interest_rate', 'remaining_tenor_months',
        'outstanding_amount', 'currency', 'notes', 'entry_date',
        'revision_notes', 'revision_date',
        'settlement_type', 'settled_amount', 'settled_via_loan_id', 'action_type',
    ];

    protected $casts = [
        'facility_amount'      => 'decimal:2',
        'outstanding_amount'   => 'decimal:2',
        'settled_amount'       => 'decimal:2',
        'interest_rate'        => 'decimal:3',
        'granted_date'         => 'date',
        'entry_date'           => 'date',
        'revision_date'        => 'date',
        'is_active'            => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderByDesc('version');
    }

    public function settledViaLoan(): BelongsTo
    {
        return $this->belongsTo(self::class, 'settled_via_loan_id');
    }

    /**
     * Get all past archived history records for this loan root family.
     */
    public function getHistoryRecordsAttribute()
    {
        $rootId = $this->parent_id ?? $this->id;
        return self::with('user', 'bank', 'settledViaLoan.bank')
            ->where(function($q) use ($rootId) {
                $q->where('id', $rootId)->orWhere('parent_id', $rootId);
            })
            ->where('id', '!=', $this->id)
            ->orderByDesc('version')
            ->get();
    }

    /**
     * Format tenor string from raw months (e.g. 27 -> "2 Years 3 Months").
     */
    public function getFormattedTenorAttribute(): string
    {
        if (! $this->tenor) {
            return '—';
        }

        preg_match('/\d+/', $this->tenor, $matches);
        if (empty($matches)) {
            return $this->tenor;
        }

        $months = (int) $matches[0];
        if ($months <= 0) {
            return $this->tenor;
        }

        if ($months >= 12) {
            $years = floor($months / 12);
            $remMonths = $months % 12;
            $yStr = $years . ($years == 1 ? ' Year' : ' Years');
            if ($remMonths > 0) {
                $mStr = $remMonths . ($remMonths == 1 ? ' Month' : ' Months');
                return "{$yStr} {$mStr}";
            }
            return $yStr;
        }

        return $months . ($months == 1 ? ' Month' : ' Months');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
