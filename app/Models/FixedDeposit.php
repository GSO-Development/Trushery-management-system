<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedDeposit extends Model
{
    protected $fillable = [
        'company_id', 'bank_id', 'user_id',
        'parent_id', 'is_active', 'version',
        'amount', 'currency',
        'commencement_date', 'maturity_date', 'tenor',
        'interest_rate', 'renewal_instructions', 'pledged_details', 'entry_date',
        'revision_notes', 'revision_date',
        'action_type', 'withdrawal_type', 'withdrawn_amount',
    ];

    protected $casts = [
        'amount'             => 'decimal:2',
        'withdrawn_amount'   => 'decimal:2',
        'interest_rate'      => 'decimal:3',
        'commencement_date'  => 'date',
        'maturity_date'      => 'date',
        'entry_date'         => 'date',
        'revision_date'      => 'date',
        'is_active'          => 'boolean',
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

    public function getHistoryRecordsAttribute()
    {
        $rootId = $this->parent_id ?? $this->id;
        return self::with('user', 'bank')
            ->where(function($q) use ($rootId) {
                $q->where('id', $rootId)->orWhere('parent_id', $rootId);
            })
            ->where('id', '!=', $this->id)
            ->orderByDesc('version')
            ->get();
    }

    /**
     * Calculate monthly interest profit for this FD.
     */
    public function getMonthlyProfitAttribute(): float
    {
        if (!$this->amount || !$this->interest_rate) {
            return 0.00;
        }
        return ($this->amount * ($this->interest_rate / 100)) / 12;
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

    public function getDaysToMaturityAttribute(): int
    {
        if (! $this->maturity_date) {
            return 0;
        }
        return now()->diffInDays($this->maturity_date, false);
    }

    public function getAutoTenorAttribute(): string
    {
        if (! $this->commencement_date || ! $this->maturity_date) {
            return $this->tenor ?? '—';
        }
        $days = $this->commencement_date->diffInDays($this->maturity_date);
        if ($days >= 365) {
            return round($days / 365, 1) . ' Years';
        }
        if ($days >= 30) {
            return round($days / 30) . ' Months';
        }
        return $days . ' Days';
    }
}
