<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Abonnement d'un tenant à un pack.
 *
 * Ce modèle N'utilise PAS le TenantScope : il est systématiquement accédé via
 * un tenant explicite (`$tenant->subscriptions()`) ou par le super-admin/les
 * jobs (contexte global). Un scope global provoquerait un double filtrage sur
 * la relation `Tenant::subscriptions()` (tenant_id = X AND tenant_id = contexte).
 */
class TenantSubscription extends Model
{
    public const STATUS_TRIAL = 'trial';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_PAST_DUE = 'past_due';

    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_ANNUAL = 'annual';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'pack_id',
        'status',
        'billing_period',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'auto_renew',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'auto_renew' => 'boolean',
        ];
    }

    /**
     * The tenant owning this subscription.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The pack of this subscription (null pendant un essai gratuit).
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }

    /**
     * Indique si l'abonnement est « courant » (essai ou actif non expiré).
     */
    public function isCurrent(): bool
    {
        if (! in_array($this->status, [self::STATUS_TRIAL, self::STATUS_ACTIVE], true)) {
            return false;
        }

        if ($this->status === self::STATUS_ACTIVE && $this->ends_at?->isPast()) {
            return false;
        }

        if ($this->status === self::STATUS_TRIAL && $this->trial_ends_at?->isPast()) {
            return false;
        }

        return true;
    }
}
