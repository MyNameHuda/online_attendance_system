<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftSwapRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING_TARGET = 'pending_target';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'requester_id',
        'target_employee_id',
        'date',
        'requester_shift_id',
        'target_shift_id',
        'reason',
        'target_response_note',
        'approver_response_note',
        'status',
        'target_responded_at',
        'approver_id',
        'approver_decided_at',
    ];

    protected $casts = [
        'date' => 'date',
        'target_responded_at' => 'datetime',
        'approver_decided_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function targetEmployee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_employee_id');
    }

    public function requesterShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'requester_shift_id');
    }

    public function targetShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'target_shift_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function isPendingTarget(): bool
    {
        return $this->status === self::STATUS_PENDING_TARGET;
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ], true);
    }
}
