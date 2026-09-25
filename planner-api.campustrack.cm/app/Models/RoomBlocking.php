<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomBlocking extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'start_datetime',
        'end_datetime',
        'reason',
        'blocking_type',
        'created_by',
        'is_recurring',
        'recurrence_pattern',
    ];

    protected $casts = [
        'room_id' => 'integer',
        'created_by' => 'integer',
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'is_recurring' => 'boolean',
        'recurrence_pattern' => 'array',
    ];

    /**
     * Get the room that is being blocked.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Scope for rejected blockings.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for approved blockings.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for active blockings (approved and in the future or currently active).
     */
    public function scopeActive($query)
    {
        return $query->where('end_datetime', '>=', now());
    }

    /**
     * Scope for blockings in a given period.
     */
    public function scopeForPeriod($query, $start, $end)
    {
        return $query->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start);
    }

    /**
     * Scope for blockings that conflict with a given period.
     */
    public function scopeConflictsWith($query, $start, $end)
    {
        return $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('start_datetime', [$start, $end])
                ->orWhereBetween('end_datetime', [$start, $end])
                ->orWhere(function ($sq) use ($start, $end) {
                    $sq->where('start_datetime', '<=', $start)
                        ->where('end_datetime', '>=', $end);
                });
        });
    }

    /**
     * Check if this blocking conflicts with a given datetime range.
     */
    public function conflictsWith($start, $end): bool
    {
        return $this->start_datetime < $end && $this->end_datetime > $start;
    }

    /**
     * Get duration in hours.
     */
    public function getDurationInHours(): float
    {
        return $this->start_datetime->diffInHours($this->end_datetime);
    }
}
