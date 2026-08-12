<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'user_name',
        'action',
        'module',
        'description',
        'ip_address',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to log audit trail action.
     */
    public static function log(?int $companyId, string $action, string $module, string $description): self
    {
        $user = auth()->user();
        return static::create([
            'company_id'  => $companyId ?? $user?->company_id,
            'user_id'     => $user?->id,
            'user_name'   => $user?->name ?? 'System',
            'action'      => strtoupper($action),
            'module'      => $module,
            'description' => $description,
            'ip_address'  => request()->ip(),
        ]);
    }
}
