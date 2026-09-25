<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Planning\Entities\ShiftPlanning;

class Course extends Model
{
    use HasFactory;

    protected $table = 'courses';

    protected $fillable = [
        'name',
        'code',
        'description',
        'department_id',
        'coefficient',
        'hours_per_week',
        'is_active',
    ];

    protected $casts = [
        'coefficient' => 'decimal:1',
        'hours_per_week' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the department that owns the course.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The teachers that belong to the course.
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_course');
    }

    /**
     * Get the shift plannings for the course.
     */
    public function shiftPlannings(): HasMany
    {
        return $this->hasMany(ShiftPlanning::class, 'course_id');
    }
}
