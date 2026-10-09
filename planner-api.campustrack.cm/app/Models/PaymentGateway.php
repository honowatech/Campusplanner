<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuration PayMe (MamoniPay) de la plateforme, gérée par le super-admin.
 * Le mot de passe est chiffré (cast `encrypted`).
 */
class PaymentGateway extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'provider',
        'user_name',
        'password',
        'app_id',
        'endpoint',
        'pay_type_id',
        'client_fees_rate',
        'mode',
        'is_active',
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
            'client_fees_rate' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * La configuration PayMe active (singleton plateforme).
     */
    public static function active(): ?self
    {
        return static::query()->where('is_active', true)->first();
    }
}
