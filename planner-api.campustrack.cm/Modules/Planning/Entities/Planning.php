<?php

namespace Modules\Planning\Entities;

use App\Models\Department;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planning extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'planning_plannings';

    protected $fillable = [
        'type',
        'starting_date',
        'ending_date',
        'description',
        'department_id',
        'tenant_id',
    ];

    protected $casts = [
        'starting_date' => 'date',
        'ending_date' => 'date',
        'department_id' => 'integer',
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
     * Département propriétaire du planning.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
