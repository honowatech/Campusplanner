<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Planning\Entities\ShiftPlanning;

class Teacher extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'teachers';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'speciality',
        'department_id',
        'max_hours_per_week',
        'is_active',
        'hired_at',
        'tenant_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'department_id' => 'integer',
        'max_hours_per_week' => 'integer',
        'is_active' => 'boolean',
        'hired_at' => 'date',
    ];

    protected $appends = ['full_name'];

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * Get the user that owns the teacher.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the department that owns the teacher.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The courses that belong to the teacher.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'teacher_course');
    }

    /**
     * Get the blockings for the teacher.
     */
    public function blockings(): HasMany
    {
        return $this->hasMany(TeacherBlocking::class, 'teacher_id');
    }

    /**
     * Get the shift plannings for the teacher.
     */
    public function shiftPlannings(): HasMany
    {
        return $this->hasMany(ShiftPlanning::class, 'teacher_id');
    }

    /**
     * Get approved blockings only.
     */
    public function approvedBlockings(): HasMany
    {
        return $this->blockings()->where('status', 'approved');
    }

    /**
     * Check if teacher has available hours for a given period.
     */
    public function hasAvailableHours(float $hoursNeeded, string $weekStart): bool
    {
        $currentWeekHours = $this->shiftPlannings()
            ->whereBetween('date', [$weekStart, date('Y-m-d', strtotime($weekStart.' + 6 days'))])
            ->sum(\hours_diff_expr());

        return ($currentWeekHours + $hoursNeeded) <= $this->max_hours_per_week;
    }
}
