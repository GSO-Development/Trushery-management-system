<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankEntry extends Model
{
    protected $fillable = [
        'company_id',
        'bank_id',
        'user_id',
        'interest_rate',
        'available_amount',
        'notes',
        'entry_date',
    ];

    protected $casts = [
        'interest_rate'    => 'decimal:3',
        'available_amount' => 'decimal:2',
        'entry_date'       => 'datetime',
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
