<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'group_type',
        'company_ids',
        'nav_permissions',
        'email_notifications_enabled',
    ];

    protected $casts = [
        'nav_permissions'             => 'array',
        'company_ids'                 => 'array',
        'email_notifications_enabled' => 'boolean',
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
     * Check if this is a Multi-Company Group (CEO type).
     */
    public function isGroup(): bool
    {
        return ($this->group_type ?? 'individual') === 'group';
    }

    /**
     * Check if this is an Individual Sub-Company group.
     */
    public function isIndividual(): bool
    {
        return ! $this->isGroup();
    }

    /**
     * Get all companies associated with this group.
     */
    public function getAssignedCompanies(): Collection
    {
        if ($this->isGroup()) {
            return Company::whereIn('id', $this->company_ids ?? [])->orderBy('name')->get();
        }

        return $this->company ? new Collection([$this->company]) : new Collection();
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

    /**
     * Check if email notifications are enabled for this group.
     */
    public function hasEmailNotifications(): bool
    {
        return (bool) $this->email_notifications_enabled;
    }
}
