<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyLoan extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'bank_name',
        'loan_type',
        'principal_amount',
        'outstanding_balance',
        'interest_rate',
        'monthly_installment',
        'due_date',
    ];

    protected $casts = [
        'due_date'           => 'date',
        'principal_amount'   => 'decimal:2',
        'outstanding_balance'=> 'decimal:2',
        'interest_rate'      => 'decimal:2',
        'monthly_installment'=> 'decimal:2',
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
