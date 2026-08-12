<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashPositionEntry extends Model
{
    protected $fillable = [
        'company_id',
        'bank_id',
        'company_bank_account_id',
        'entry_date',
        'opening_balance',
        'cash_in',
        'cash_out',
        'restricted_cash',
        'closing_balance',
        'currency',
        'remarks',
        'user_id',
    ];

    protected $casts = [
        'entry_date'       => 'date',
        'opening_balance'  => 'float',
        'cash_in'          => 'float',
        'cash_out'         => 'float',
        'restricted_cash'  => 'float',
        'closing_balance'  => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function companyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_bank_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
