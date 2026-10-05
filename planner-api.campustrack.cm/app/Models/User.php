<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
        'requested_role',
        'is_approved',
        'is_demo',
        'approved_by',
        'approved_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_approved' => 'boolean',
            'is_demo' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Get the department that owns the user.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the teacher profile linked to this account (if any).
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * Get the student profile linked to this account (if any).
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Identifiants des classes visibles par l'utilisateur (scope « classe ») :
     * la classe de l'étudiant + les classes où il enseigne (professeur).
     *
     * @return list<int>
     */
    public function classIds(): array
    {
        $ids = [];

        if ($this->student?->course_class_id) {
            $ids[] = $this->student->course_class_id;
        }

        if ($this->teacher) {
            $ids = array_merge($ids, $this->teacher->shiftPlannings()
                ->pluck('course_class_id')
                ->unique()
                ->filter()
                ->all());
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Identifiants des matières (cours) visibles par l'utilisateur (scope « matière »).
     *
     * @return list<int>
     */
    public function courseIds(): array
    {
        if (! $this->teacher) {
            return [];
        }

        return $this->teacher->courses()->pluck('courses.id')->all();
    }
}
