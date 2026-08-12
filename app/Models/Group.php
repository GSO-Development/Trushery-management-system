<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $fillable = ['company_id', 'name', 'nav_permissions'];

    protected $casts = [
        'nav_permissions' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the array of nav permission keys for this group.
     * e.g. ['summary_dashboard', 'rate_management']
     */
    public function getNavKeys(): array
    {
        return $this->nav_permissions ?? [];
    }

    /**
     * Check if this group has a specific nav key permission.
     */
    public function hasNavPermission(string $key): bool
    {
        return in_array($key, $this->getNavKeys(), true);
    }
}
