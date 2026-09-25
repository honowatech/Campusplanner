<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Planning\Entities\ShiftPlanning;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'department_id',
        'type',
        'capacity',
        'floor',
        'building',
        'has_projector',
        'has_computers',
        'has_whiteboard',
        'is_active',
        'description',
    ];

    protected $casts = [
        'department_id' => 'integer',
        'capacity' => 'integer',
        'floor' => 'integer',
        'has_projector' => 'boolean',
        'has_computers' => 'boolean',
        'has_whiteboard' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the department that owns the room.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the blockings for this room.
     */
    public function blockings(): HasMany
    {
        return $this->hasMany(RoomBlocking::class);
    }

    /**
     * Get the shift plannings for this room.
     */
    public function shiftPlannings(): HasMany
    {
        return $this->hasMany(ShiftPlanning::class, 'room_id');
    }

    /**
     * Get the classes assigned to this room.
     */
    public function classes(): HasMany
    {
        return $this->hasMany(CourseClass::class, 'room_id');
    }

    /**
     * Scope for active rooms.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for rooms with minimum capacity.
     */
    public function scopeMinCapacity($query, $capacity)
    {
        return $query->where('capacity', '>=', $capacity);
    }

    /**
     * Scope for rooms by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if room is available at given datetime range.
     */
    public function isAvailable($startDateTime, $endDateTime, $excludePlanningId = null): bool
    {
        // Check blockings
        $hasBlocking = $this->blockings()
            ->where('start_datetime', '<', $endDateTime)
            ->where('end_datetime', '>', $startDateTime)
            ->exists();

        if ($hasBlocking) {
            return false;
        }

        // Check shift plannings
        $query = $this->shiftPlannings()
            ->where('date', date('Y-m-d', strtotime($startDateTime)))
            ->where(function ($q) use ($startDateTime, $endDateTime) {
                $q->whereBetween('starting_hour', [
                    date('H:i:s', strtotime($startDateTime)),
                    date('H:i:s', strtotime($endDateTime)),
                ])
                    ->orWhereBetween('ending_hour', [
                        date('H:i:s', strtotime($startDateTime)),
                        date('H:i:s', strtotime($endDateTime)),
                    ]);
            });

        if ($excludePlanningId) {
            $query->where('id', '!=', $excludePlanningId);
        }

        return ! $query->exists();
    }
}
