<?php

namespace App\Models;

use App\Support\TenantContext;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'demo_mode',
        'max_weekly_hours_per_teacher',
        'max_consecutive_hours',
        'max_daily_hours_per_teacher',
        'max_daily_hours_per_class',
        'enable_room_conflict',
        'enable_teacher_conflict',
        'enable_group_conflict',
        'sms_config',
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
            'max_weekly_hours_per_teacher' => 'integer',
            'max_consecutive_hours' => 'integer',
            'max_daily_hours_per_teacher' => 'integer',
            'max_daily_hours_per_class' => 'integer',
            'enable_room_conflict' => 'boolean',
            'enable_teacher_conflict' => 'boolean',
            'enable_group_conflict' => 'boolean',
            'sms_config' => 'array',
        ];
    }

    /**
     * Récupère (ou crée) la ligne de réglages du tenant courant.
     *
     * En contexte global (super-admin / CLI), tenant_id est NULL : c'est la
     * config « plateforme ». Pour un utilisateur de tenant, la ligne est scopée
     * à son école.
     */
    public static function instance(): self
    {
        return static::query()->firstOrCreate(
            ['tenant_id' => TenantContext::currentId()],
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
