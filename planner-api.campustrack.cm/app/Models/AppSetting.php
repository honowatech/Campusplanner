<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'demo_mode',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'demo_mode' => 'boolean',
        ];
    }

    /**
     * Récupère (ou crée) la ligne singleton des réglages de l'application.
     */
    public static function instance(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            ['demo_mode' => false]
        );
    }

    /**
     * Indique si le mode démo est activé.
     */
    public static function demoModeEnabled(): bool
    {
        return (bool) static::instance()->demo_mode;
    }
}
