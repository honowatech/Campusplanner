<?php

namespace App\Models;

use App\Enums\V1\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paiement d'abonnement d'un tenant via PayMe.
 *
 * Comme TenantSubscription, ce modèle n'utilise pas le TenantScope : il est
 * accédé via un tenant explicite ou par le super-admin (contexte global).
 */
class Payment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'amount',
        'currency',
        'status',
        'gateway_reference',
        'external_reference',
        'idempotency_key',
        'payment_method',
        'metadata',
        'paid_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'metadata' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * The tenant owning this payment.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The subscription this payment relates to (if any).
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class);
    }

    /**
     * Statut courant sous forme d'enum.
     */
    public function statusEnum(): PaymentStatus
    {
        return PaymentStatus::from($this->status);
    }
}
