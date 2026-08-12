<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRepaymentSchedule extends Model
{
    protected $fillable = [
        'company_id',
        'bank_id',
        'loan_category',
        'loan_id',
        'due_date',
        'principal_amount',
        'interest_amount',
        'total_installment',
        'status',
        'paid_date',
        'currency',
        'remarks',
        'user_id',
    ];

    protected $casts = [
        'due_date'          => 'date',
        'paid_date'         => 'date',
        'principal_amount'  => 'float',
        'interest_amount'   => 'float',
        'total_installment' => 'float',
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
