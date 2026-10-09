<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pack extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'tier',
        'description',
        'price',
        'currency',
        'billing_period',
        'is_active',
        'sort',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'float',
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /**
     * The features included in this pack.
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'pack_feature');
    }

    /**
     * The subscriptions referencing this pack.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    /**
     * Scope : packs actifs uniquement.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
