<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'name',
        'is_national',
    ];

    protected $casts = [
        'date' => 'date',
        'is_national' => 'boolean',
    ];

    public static function isHoliday(string $date): bool
    {
        return self::whereDate('date', $date)->exists();
    }

    public static function holidayName(string $date): ?string
    {
        return self::whereDate('date', $date)->value('name');
    }
}
