<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityTransaction extends Model
{
    protected $fillable = [
        'company_id',
        'bank_id',
        'facility_category',
        'transaction_type',
        'reference_number',
        'amount',
        'transaction_date',
        'currency',
        'remarks',
        'user_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount'           => 'float',
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
