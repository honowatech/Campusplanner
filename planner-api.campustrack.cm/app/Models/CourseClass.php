<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Planning\Entities\ShiftPlanning;

class CourseClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'code',
        'department_id',
        'level',
        'capacity',
        'room_id',
        'academic_year',
        'is_active',
    ];

    protected $casts = [
        'department_id' => 'integer',
        'room_id' => 'integer',
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the department that owns the class.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the room assigned to this class (if any).
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the students in this class.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'course_class_id');
    }

    /**
     * Get the shift plannings for this class.
     */
    public function shiftPlannings(): HasMany
    {
        return $this->hasMany(ShiftPlanning::class, 'course_class_id');
    }

    /**
     * Get the teachers assigned to this class through shift plannings.
     */
    public function teachers()
    {
        return $this->hasManyThrough(
            Teacher::class,
            ShiftPlanning::class,
            'course_class_id',
            'id',
            'id',
            'teacher_id'
        );
    }

    /**
     * Check if class has available capacity.
     */
    public function hasAvailableSpace(): bool
    {
        return $this->students()->count() < $this->capacity;
    }

    /**
     * Get available spots in the class.
     */
    public function getAvailableSpots(): int
    {
        return $this->capacity - $this->students()->count();
    }
}
