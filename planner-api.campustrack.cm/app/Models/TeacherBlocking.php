<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherBlocking extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'teacher_id',
        'start_datetime',
        'end_datetime',
        'reason',
        'blocking_type',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'tenant_id',
    ];

    protected $casts = [
        'teacher_id' => 'integer',
        'approved_by' => 'integer',
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the teacher that this blocking belongs to.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    /**
     * Get the user who approved the blocking.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for pending blockings.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved blockings.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected blockings.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for active blockings (approved and in the future or currently active).
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'approved')
            ->where('end_datetime', '>=', now());
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
     * Check if this blocking conflicts with a given datetime range.
     */
    public function conflictsWith($start, $end): bool
    {
        return $this->start_datetime < $end && $this->end_datetime > $start;
    }

    /**
     * Approve the blocking.
     */
    public function approve($userId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject the blocking.
     */
    public function reject($userId, $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Check if blocking is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if blocking is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if blocking is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Get duration in hours.
     */
    public function getDurationInHours(): float
    {
        return $this->start_datetime->diffInHours($this->end_datetime);
    }
}
