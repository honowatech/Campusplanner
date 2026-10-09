<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Journal idempotent des callbacks PayMe reçus (dédup par `hash` UNIQUE).
 */
class PaymentWebhook extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'hash',
        'payload',
        'received_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'received_at' => 'datetime',
        ];
    }
}
