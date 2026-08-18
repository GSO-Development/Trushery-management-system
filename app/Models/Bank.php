<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    protected $fillable = [
        'name',
        'short_name',
        'bank_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the bank short code or fallback.
     */
    public function getShortCodeDisplayAttribute(): string
    {
        return $this->short_name ?: ($this->bank_code ?: $this->name);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_bank');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(BankEntry::class);
    }

    public function companyBankAccounts(): HasMany
    {
        return $this->hasMany(CompanyBankAccount::class);
    }
}