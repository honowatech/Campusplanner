<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'status',
        'is_demo',
        'demo_mode',
        'settings',
        'billing_phone',
        'trial_ends_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_demo' => 'boolean',
            'demo_mode' => 'boolean',
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Le tenant démo persistant (s'il existe).
     */
    public static function demo(): ?self
    {
        return static::query()->where('is_demo', true)->first();
    }

    /**
     * Indique si le mode démo est activé sur ce tenant.
     */
    public function demoModeEnabled(): bool
    {
        return (bool) $this->demo_mode;
    }

    /**
     * The users belonging to this tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * The departments belonging to this tenant.
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * The subscriptions of this tenant.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    /**
     * The SMS credentials of this tenant (one).
     */
    public function smsCredential(): HasOne
    {
        return $this->hasOne(SmsCredential::class);
    }

    /**
     * The SMS logs of this tenant.
     */
    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }
}
