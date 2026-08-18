<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'group_id',
        'is_admin',
        'is_ceo',
        'azure_id',
        'auth_provider',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
            'is_ceo'            => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Companies this CEO user can access (many-to-many via ceo_company pivot).
     */
    public function ceoCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'ceo_company');
    }

    /**
     * Check if the user has CEO or Group-level multi-company access.
     */
    public function isCeoOrGroupUser(): bool
    {
        return $this->is_ceo || ($this->group && $this->group->isGroup());
    }

    /**
     * Dynamically resolve the active company.
     * If user has no single company_id (e.g. Group/CEO), resolve from route slug if available.
     */
    public function getCompanyAttribute()
    {
        if ($this->company_id && $this->relationLoaded('company') && $this->getRelation('company')) {
            return $this->getRelation('company');
        }

        if ($this->company_id) {
            return $this->getRelationValue('company');
        }

        if (request() && request()->route('company_slug')) {
            return Company::where('slug', request()->route('company_slug'))->first();
        }

        return null;
    }

    /**
     * Determine the redirect route after login based on role.
     */
    public function getPostLoginRedirect(): string
    {
        if ($this->is_admin) {
            return route('admin.dashboard');
        }

        // CEO or Group-type Access Group: redirect to Group multi-company dashboard
        if ($this->is_ceo || ($this->group && $this->group->isGroup())) {
            return route('group.dashboard');
        }

        if ($this->company && $this->group) {
            $companySlug = $this->company->slug;
            $navKeys     = $this->group->getNavKeys();

            if (! empty($navKeys)) {
                // Always prefer summary_dashboard as the landing page if the group has access to it
                if (in_array('summary_dashboard', $navKeys, true)) {
                    return url("/{$companySlug}/summary-dashboard");
                }

                // Fallback to first nav key
                $firstKey = $navKeys[0];
                $pageSlug = str_replace('_', '-', $firstKey);

                return url("/{$companySlug}/{$pageSlug}");
            }
        }

        return route('login');
    }
}