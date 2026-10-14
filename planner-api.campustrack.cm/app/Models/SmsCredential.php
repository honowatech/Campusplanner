<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Crédences SMS d'un tenant (fournisseur Nexah).
 *
 * Comme TenantSubscription, ce modèle n'utilise pas le TenantScope : il est
 * accédé via un tenant explicite (`$tenant->smsCredential`) ou par un
 * contrôleur scopé sur l'utilisateur courant.
 */
class SmsCredential extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'provider',
        'user',
        'password',
        'sender_id',
        'is_active',
        'balance_cached',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'is_active' => 'boolean',
            'balance_cached' => 'float',
        ];
    }

    /**
     * The tenant owning these credentials.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
