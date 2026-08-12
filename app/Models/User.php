<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
     * Determine the redirect route after login based on role.
     */
    public function getPostLoginRedirect(): string
    {
        if ($this->is_admin) {
            return route('admin.dashboard');
        }

        // CEO: redirect to CEO multi-company dashboard
        if ($this->is_ceo) {
            return route('ceo.dashboard');
        }

        if ($this->company && $this->group) {
            $companySlug = $this->company->slug;
            $navKeys     = $this->group->getNavKeys();

            if (! empty($navKeys)) {
                $firstKey  = $navKeys[0];
                $pageSlug  = str_replace('_', '-', $firstKey);

                return url("/{$companySlug}/{$pageSlug}");
            }
        }

        return route('login');
    }
}

