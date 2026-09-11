<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    public const STATUS_KERJA = 'kerja';
    public const STATUS_LIBUR = 'libur';

    protected $fillable = [
        'user_id',
        'date',
        'shift_id',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function isKerja(): bool
    {
        return $this->status === self::STATUS_KERJA;
    }

    public function isLibur(): bool
    {
        return $this->status === self::STATUS_LIBUR;
    }
}
