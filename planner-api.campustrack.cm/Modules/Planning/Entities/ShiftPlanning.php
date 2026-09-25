<?php

namespace Modules\Planning\Entities;

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Room;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftPlanning extends Model
{
    use HasFactory;

    protected $table = 'planning_shift_plannings';

    protected $fillable = [
        'planning_id',
        'course_class_id',
        'room_id',
        'teacher_id',
        'course_id',
        'date',
        'starting_hour',
        'ending_hour',
        'number_teachers',
        'status',
        'notes',
        'is_recurring',
        'recurrence_pattern',
        'parent_id',
        'recurrence_end_date',
    ];

    protected $casts = [
        'date' => 'date',
        'starting_hour' => 'datetime:H:i:s',
        'ending_hour' => 'datetime:H:i:s',
        'is_recurring' => 'boolean',
        'recurrence_pattern' => 'array',
        'recurrence_end_date' => 'date',
    ];

    public function planning()
    {
        return $this->belongsTo(Planning::class);
    }

    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class, 'course_class_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function getPeriodAttribute()
    {
        return $this->starting_hour->format('H:i').' - '.$this->ending_hour->format('H:i');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function parent()
    {
        return $this->belongsTo(ShiftPlanning::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ShiftPlanning::class, 'parent_id');
    }

    public function isRecurring(): bool
    {
        return $this->is_recurring ?? false;
    }

    public function getRecurrenceDates(): array
    {
        if (! $this->is_recurring || ! $this->recurrence_pattern) {
            return [];
        }

        $pattern = $this->recurrence_pattern;
        $startDate = $this->date instanceof Carbon
            ? $this->date
            : Carbon::parse($this->date);
        $endDate = $this->recurrence_end_date
            ? Carbon::parse($this->recurrence_end_date)
            : $startDate->addWeeks(12);

        $dates = [];
        $currentDate = $startDate->copy();

        $frequency = $pattern['frequency'] ?? 'weekly';
        $daysOfWeek = $pattern['days'] ?? [];

        while ($currentDate->lte($endDate)) {
            if ($frequency === 'daily') {
                $dates[] = $currentDate->format('Y-m-d');
                $currentDate->addDay();
            } elseif ($frequency === 'weekly') {
                if (in_array($currentDate->dayOfWeek, $daysOfWeek)) {
                    $dates[] = $currentDate->format('Y-m-d');
                }
                $currentDate->addDay();
            } elseif ($frequency === 'biweekly') {
                if (in_array($currentDate->dayOfWeek, $daysOfWeek)) {
                    $dates[] = $currentDate->format('Y-m-d');
                }
                $currentDate->addDay();
            }
        }

        return $dates;
    }
}
