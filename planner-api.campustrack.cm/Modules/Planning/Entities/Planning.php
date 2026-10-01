<?php

namespace Modules\Planning\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planning extends Model
{
    use HasFactory;

    protected $table = 'planning_plannings';

    protected $fillable = [
        'starting_date',
        'ending_date',
        'description',
    ];

    protected $casts = [
        'starting_date' => 'date',
        'ending_date' => 'date',
    ];

    public function getPeriodAttribute()
    {
        return $this->starting_date->format('d M Y').' - '.$this->ending_date->format('d M Y');
    }

    public function shiftPlannings()
    {
        return $this->hasMany(ShiftPlanning::class, 'planning_id');
    }

    /**
     * Déduit le département du planning depuis son premier shift planning :
     * la table planning_plannings ne porte pas de colonne department_id.
     */
    public function getDepartmentIdAttribute(): ?int
    {
        return $this->shiftPlannings()->first()?->courseClass?->department_id;
    }
}
