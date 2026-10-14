<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'user_id',
        'course_class_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'parent_name',
        'parent_phone',
        'matricule',
        'admission_date',
        'is_active',
        'photo_url',
        'tenant_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'course_class_id' => 'integer',
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected $appends = ['full_name'];

    /**
     * Get the student's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * Get the user account associated with the student.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the class the student belongs to.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class, 'course_class_id');
    }

    /**
     * Get the department through the class.
     */
    public function department()
    {
        return $this->class?->department();
    }

    /**
     * Scope for active students.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for students in a specific class.
     */
    public function scopeInClass($query, $classId)
    {
        return $query->where('course_class_id', $classId);
    }

    /**
     * Calculate student age.
     */
    public function getAge(): ?int
    {
        if (! $this->date_of_birth) {
            return null;
        }

        return $this->date_of_birth->age;
    }
}
