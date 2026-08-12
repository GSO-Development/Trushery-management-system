<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovementEntry extends Model
{
    protected $fillable = [
        'company_id',
        'entry_date',
        'customer_collections',
        'loan_drawdowns',
        'supplier_payments',
        'salaries',
        'taxes',
        'loan_repayments',
        'other_payments',
        'remarks',
        'user_id',
    ];

    protected $casts = [
        'entry_date'           => 'date',
        'customer_collections' => 'float',
        'loan_drawdowns'       => 'float',
        'supplier_payments'    => 'float',
        'salaries'             => 'float',
        'taxes'                => 'float',
        'loan_repayments'      => 'float',
        'other_payments'       => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
