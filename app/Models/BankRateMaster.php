<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankRateMaster extends Model
{
    protected $fillable = [
        'company_id',
        'bank_id',
        'rate_type',
        'base_rate',
        'margin',
        'effective_rate',
        'effective_date',
        'remarks',
        'user_id',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'base_rate'      => 'float',
        'margin'         => 'float',
        'effective_rate' => 'float',
    ];

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
